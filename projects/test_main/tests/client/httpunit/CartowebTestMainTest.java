import junit.framework.Test;
import junit.framework.TestCase;
import junit.framework.TestSuite;

public class CartowebTestMainTest extends AbstractCartowebTest {
    
    public CartowebTestMainTest( String name )
    {
      super( name );
    }
    
    public static void main( String args[] ) {
        AbstractCartowebTest.main(args);
        junit.textui.TestRunner.run( suite() );
    }
    
    public static Test suite()
    {
      return new TestSuite( CartowebTestMainTest.class );
    }
}
